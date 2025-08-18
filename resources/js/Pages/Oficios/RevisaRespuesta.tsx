import AppLayout from "../../Layouts/app";
import { Link, router, useForm, usePage } from "@inertiajs/react";
import { useState, Fragment, useRef, useEffect, FormEventHandler } from "react";
import {
    Card,
    Form,
    Row,
    Col,
    Button,
    Alert,
    Modal,
    ModalHeader,
    ModalBody,
    ModalTitle,
    ModalFooter,
} from "react-bootstrap";
import PageHeader from "../../Layouts/layoutcomponents/pageHeader";
import "filepond/dist/filepond.min.css";
import VerPdf from "@/types/VerPdf";
import { Head } from "@inertiajs/react";
import Swal from "sweetalert2";
import InputError from "../InputError";
import DatePicker from "react-datepicker";
import { Fecha } from "../../commondata/Fecha";
import "react-datepicker/dist/react-datepicker.css";
import toast from "react-hot-toast";

type FormIn = {
    id: number;
    fecha: string | null;
};

export default function RevisaRespuesta({
    status,
    oficio,
    archivos,
}: {
    status?: string;
    oficio: any;
    archivos: any[];
}) {
    const user = usePage().props.auth.user;
    const [show, setShow] = useState<boolean>(false);
    const [show3, setShow3] = useState(false);
    const [pdf, setPdf] = useState("");
    const [tipo, setTipo] = useState("pdf");

    const formFecha = useForm<FormIn>({
        id: oficio.id_respuesta,
        fecha: oficio.fecha_respuesta || Fecha,
    });

    const formRechazo = useForm({
        id: 0,
        descripcion: "",
    });

    const submitRechaza: FormEventHandler = (e) => {
        e.preventDefault();
        Swal.fire({
            title: "¿Está seguro?",
            text: "Se notificará al colaborador para que vuelva a responder el oficio.",
            icon: "warning",
            showDenyButton: true,
            showCancelButton: false,
            confirmButtonText: "Sí, estoy seguro",
            denyButtonText: `Cancelar`,
            customClass: {
                container: "swalSuperior",
            },
        }).then((result) => {
            if (result.isConfirmed) {
                formRechazo.put(route("rechazarResp"));
            } else {
                formRechazo.cancel();
            }
        });
    };

    const submitFecha: FormEventHandler = (e) => {
        e.preventDefault();
        formFecha.post(route("oficios.cambiaFecha"), {
            onSuccess: (page) => {
                toast(
                    "Correcto: Se actualizo la fecha de respuesta del oficio.",
                    {
                        style: {
                            padding: "25px",
                            color: "#fff",
                            backgroundColor: "#29bf74",
                        },
                        position: "top-center",
                    }
                );
            },
        });
    };

    function parseLocalDate(dateString: string): Date {
        const [year, month, day] = dateString.split("-");
        return new Date(Number(year), Number(month) - 1, Number(day));
    }

    const autorizaResp = () => {
        Swal.fire({
            title: "¿Está seguro?",
            text: "El oficio ya no se podrá editar y se marcará como finalizado",
            icon: "warning",
            showDenyButton: true,
            showCancelButton: false,
            confirmButtonText: "Sí, estoy seguro",
            denyButtonText: `Cancelar`,
        }).then((result) => {
            if (result.isConfirmed) {
                router.put(route("oficios.aceptResp", { id: oficio.id }));
            }
        });
    };

    const verArchivo = (url: string, tipo: number, extension: string) => {
        if (tipo == 1) {
            setPdf(url);
            setShow(true);
            setTipo(extension);
        } else {
            window.open(url, "_blank");
        }
    };

    return (
        <AppLayout>
            <Head>
                <title>Revisión de respuesta del colaborador</title>
                <meta
                    name="Revision de respuesta"
                    content="Revise la respuesta del oficio por parte de su colaborador"
                />
            </Head>
            <Fragment>
                <PageHeader
                    titles="Revisión de respuesta"
                    active="Revisión de respuesta"
                    items={[
                        {
                            titulo: "Mis oficios",
                            urlHeader: "/oficios/mis-oficios",
                        },
                    ]}
                />
                <Row>
                    <Col lg={12} xl={6}>
                        <Card>
                            <Card.Header>
                                <Card.Title as="h3">
                                    Información del oficio
                                </Card.Title>
                            </Card.Header>
                            <Card.Body>
                                <div className="form-row">
                                    <Col xl={4} md={6} className="mb-4">
                                        <Form.Group>
                                            <Form.Label>
                                                Ingreso de la solicitud
                                            </Form.Label>
                                            <div className="custom-controls-stacked">
                                                <label className="custom-control custom-radio-md">
                                                    <input
                                                        type="radio"
                                                        className="custom-control-input"
                                                        name="ingreso"
                                                        defaultValue="Físico"
                                                        defaultChecked={
                                                            oficio.ingreso ==
                                                            "Físico"
                                                                ? true
                                                                : false
                                                        }
                                                        disabled
                                                    />
                                                    <span className="custom-control-label">
                                                        Físico
                                                    </span>
                                                </label>
                                                <label
                                                    className="custom-control custom-radio-md"
                                                    style={{
                                                        marginLeft: "2rem",
                                                    }}
                                                >
                                                    <input
                                                        type="radio"
                                                        className="custom-control-input"
                                                        name="ingreso"
                                                        defaultValue="Email"
                                                        defaultChecked={
                                                            oficio.ingreso ==
                                                            "Email"
                                                                ? true
                                                                : false
                                                        }
                                                        disabled
                                                    />
                                                    <span className="custom-control-label">
                                                        Email
                                                    </span>
                                                </label>
                                            </div>
                                        </Form.Group>
                                    </Col>
                                </div>

                                <div className="form-row">
                                    {oficio.ingreso == "Físico" ? (
                                        <Col
                                            xs={12}
                                            sm={6}
                                            xl={4}
                                            className="mb-3"
                                        >
                                            <Form.Label>
                                                Número de oficio
                                            </Form.Label>
                                            <Form.Control
                                                name="num_oficio"
                                                defaultValue={oficio.num_oficio}
                                                type="text"
                                                disabled
                                            />
                                        </Col>
                                    ) : null}

                                    {oficio.ingreso == "Email" ? (
                                        <Col
                                            xs={12}
                                            sm={6}
                                            xl={4}
                                            className="mb-3"
                                        >
                                            <Form.Label>
                                                Número de folio
                                            </Form.Label>
                                            <Form.Control
                                                type="text"
                                                disabled
                                                name="num_folio"
                                                defaultValue={oficio.num_folio}
                                            />
                                        </Col>
                                    ) : null}

                                    <Col
                                        xs={12}
                                        sm={6}
                                        md={6}
                                        lg={6}
                                        xl={4}
                                        className="mb-3"
                                    >
                                        <Form.Label>
                                            Dependencia o Unidad Académica
                                        </Form.Label>
                                        <Form.Control
                                            defaultValue={oficio.des}
                                            type="text"
                                            disabled
                                        />
                                    </Col>

                                    <Col
                                        xs={12}
                                        sm={6}
                                        md={6}
                                        lg={6}
                                        xl={4}
                                        className="mb-3"
                                    >
                                        <Form.Label>
                                            Área para dar respuesta
                                        </Form.Label>
                                        <Form.Control
                                            defaultValue={oficio.area}
                                            type="text"
                                            disabled
                                        />
                                    </Col>
                                    <Col
                                        xs={12}
                                        sm={6}
                                        md={6}
                                        lg={6}
                                        xl={4}
                                        className="mb-3"
                                    >
                                        <Form.Label>
                                            Proceso al que impacta
                                        </Form.Label>
                                        <Form.Control
                                            defaultValue={oficio.proceso}
                                            type="text"
                                            disabled
                                        />
                                    </Col>

                                    <Col
                                        xs={12}
                                        sm={12}
                                        xl={8}
                                        style={{ padding: 40 }}
                                    >
                                        <span
                                            className="tag tag-radius tag-round tag-outline-danger"
                                            onClick={() => {
                                                setPdf(oficio.archivo),
                                                    setShow(true);
                                                setTipo("pdf");
                                            }}
                                        >
                                            Click para ver archivo
                                            <i
                                                className="fa fa-file-pdf-o"
                                                style={{ padding: 6 }}
                                            ></i>
                                        </span>
                                    </Col>
                                </div>
                                <div className="form-row">
                                    <Form.Label>
                                        Breve descripción del asunto:
                                    </Form.Label>
                                    <textarea
                                        className="form-control"
                                        name="descripcion"
                                        defaultValue={oficio.descripcion}
                                        rows={3}
                                        disabled
                                    ></textarea>
                                </div>
                            </Card.Body>
                        </Card>
                    </Col>
                    <Col lg={12} xl={6}>
                        <Card>
                            <Card.Header>
                                <Card.Title as="h3">
                                    Respuesta del colaborador
                                </Card.Title>
                            </Card.Header>
                            <Card.Body>
                                {oficio.comentario !== null ? (
                                    <Col xs={12} className="mb-5">
                                        <Form.Label>
                                            Breve descripción del rechazo por
                                            parte de recepción documental:
                                        </Form.Label>
                                        <textarea
                                            className="form-control"
                                            value={oficio.comentario}
                                            rows={3}
                                            disabled
                                        ></textarea>
                                    </Col>
                                ) : null}

                                <Col xs={12} style={{ padding: 40 }}>
                                    <span
                                        className="tag tag-radius tag-round tag-outline-danger"
                                        onClick={() => {
                                            setPdf(
                                                `imprime/pdf/0/${oficio.id}`
                                            ),
                                                setTipo("pdf");
                                            setShow(true);
                                        }}
                                    >
                                        Click para ver archivo
                                        <i
                                            className="fa fa-file-pdf-o"
                                            style={{ padding: 6 }}
                                        ></i>
                                    </span>
                                </Col>

                                {archivos.length > 0 ? (
                                    <>
                                        <Col xs={12}>
                                            <table className="table table-bordered table-hover table-striped">
                                                <thead>
                                                    <tr>
                                                        <th colSpan={2}>
                                                            Archivos adjuntos
                                                        </th>
                                                    </tr>
                                                    <tr>
                                                        <th>Nombre</th>
                                                        <th>Ver</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {archivos.map((x) => {
                                                        return (
                                                            <tr key={x.id}>
                                                                <td>
                                                                    {x.nombre}
                                                                </td>
                                                                <td>
                                                                    <Button
                                                                        className="btn-icon ml-1"
                                                                        variant="danger"
                                                                        title="Ver PDF del oficio"
                                                                        onClick={() =>
                                                                            verArchivo(
                                                                                x.url,
                                                                                x.tipo,
                                                                                x.extension
                                                                            )
                                                                        }
                                                                    >
                                                                        <i className="fa fa-eye"></i>
                                                                    </Button>
                                                                </td>
                                                            </tr>
                                                        );
                                                    })}
                                                </tbody>
                                            </table>
                                        </Col>
                                        <Col
                                            xs={12}
                                            className="mb-5 d-flex justify-content-end"
                                        >
                                            <a
                                                href={route(
                                                    "oficios.downloadFiles",
                                                    {
                                                        id: oficio.id,
                                                    }
                                                )}
                                                target="_BLANK"
                                                className="btn btn-warning btn-lg mb-1"
                                            >
                                                Descargar todos los archivos
                                                adjuntos&nbsp;&nbsp;
                                                <i className="fa fa-download"></i>
                                            </a>
                                        </Col>
                                    </>
                                ) : null}

                                {user.rol == 5 ? (
                                    <form onSubmit={submitFecha}>
                                        <Col xs={4}>
                                            <Form.Label>
                                                Seleccione la fecha de respuesta
                                                del oficio:
                                            </Form.Label>
                                            <DatePicker
                                                className="form-control"
                                                selected={
                                                    formFecha.data.fecha
                                                        ? parseLocalDate(
                                                              formFecha.data
                                                                  .fecha
                                                          )
                                                        : null
                                                }
                                                onChange={(date) => {
                                                    if (date) {
                                                        const yyyy =
                                                            date.getFullYear();
                                                        const mm = String(
                                                            date.getMonth() + 1
                                                        ).padStart(2, "0");
                                                        const dd = String(
                                                            date.getDate()
                                                        ).padStart(2, "0");
                                                        formFecha.setData(
                                                            "fecha",
                                                            `${yyyy}-${mm}-${dd}`
                                                        );
                                                    }
                                                }}
                                                dateFormat="yyyy-MM-dd"
                                            />
                                        </Col>
                                        <Col xs={4} className="mt-5">
                                            <Button
                                                className="btn btn-success "
                                                type="submit"
                                            >
                                                Guardar fecha
                                            </Button>
                                        </Col>
                                        <Col xs={12} className="mb-5"></Col>
                                    </form>
                                ) : null}
                                <div className="form-row">
                                    <Col
                                        xs={12}
                                        xl={6}
                                        xxl={4}
                                        className="d-flex justify-content-center"
                                    >
                                        <Button
                                            className="btn-lg mb-1"
                                            variant="success"
                                            onClick={autorizaResp}
                                        >
                                            {user.rol == 5
                                                ? "Aceptar respuesta"
                                                : "Autorizar respuesta"}
                                        </Button>
                                    </Col>

                                    <Col
                                        xs={12}
                                        xl={6}
                                        xxl={4}
                                        className="d-flex justify-content-center"
                                    >
                                        <Button
                                            className="btn-lg mb-1"
                                            variant="danger"
                                            onClick={() => {
                                                setShow3(true),
                                                    formRechazo.setData(
                                                        "id",
                                                        oficio.id
                                                    );
                                            }}
                                        >
                                            Rechazar respuesta
                                        </Button>
                                    </Col>

                                    <Col
                                        xs={12}
                                        xl={12}
                                        xxl={4}
                                        className="d-flex justify-content-center"
                                    >
                                        <Link
                                            href={route("oficioResponder", {
                                                id: oficio.id,
                                            })}
                                            className="btn btn-primary btn-lg"
                                        >
                                            {user.rol == 5
                                                ? "Editar respuesta"
                                                : "Responder oficio (Ignorando respuesta)"}
                                        </Link>
                                    </Col>
                                </div>
                            </Card.Body>
                        </Card>
                    </Col>
                </Row>
                <VerPdf
                    urlPdf={pdf}
                    show={show}
                    setShow={setShow}
                    tipo={tipo}
                />

                <Modal show={show3} onHide={() => setShow3(false)}>
                    <ModalHeader>
                        <ModalTitle as="h5">
                            Rechazar Respuesta de Oficio
                        </ModalTitle>
                    </ModalHeader>
                    <form onSubmit={submitRechaza}>
                        <ModalBody>
                            <Row>
                                <Col xs={12}>
                                    <Form.Label>
                                        Breve descripción del motivo del
                                        rechazo:
                                    </Form.Label>
                                    <textarea
                                        className={
                                            formRechazo.errors.descripcion
                                                ? "form-control inputError"
                                                : "form-control"
                                        }
                                        name="descripcion"
                                        value={formRechazo.data.descripcion}
                                        onChange={(e) =>
                                            formRechazo.setData(
                                                "descripcion",
                                                e.target.value
                                            )
                                        }
                                        placeholder="Máximo 500 caracteres"
                                        rows={2}
                                    ></textarea>
                                    <InputError
                                        className="mt-1"
                                        message={formRechazo.errors.descripcion}
                                    />
                                </Col>
                            </Row>
                        </ModalBody>
                        <ModalFooter>
                            <Button
                                variant="secondary"
                                onClick={() => setShow3(false)}
                            >
                                Cancelar
                            </Button>
                            <Button variant="primary" type="submit">
                                Rechazar respuesta
                            </Button>
                        </ModalFooter>
                    </form>
                </Modal>
            </Fragment>
        </AppLayout>
    );
}
